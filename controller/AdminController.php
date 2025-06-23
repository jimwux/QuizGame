<?php
require_once __DIR__ . '/../libs/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;
class AdminController
{
    private $model;
    private $view;

    public function __construct($model, $view)
    {
        $this->model = $model;
        $this->view = $view;
    }

    public function show()
    {
        $usuario = $_SESSION["username"] ?? null;
        $filtro = $_GET['filtro_fecha'] ?? 'semana';

        $data = [
            "usuario" => $this->model->getUserByUsername($usuario),
            'filtroSeleccionado' => $filtro,
            'filtroDia' => $filtro === 'dia',
            'filtroSemana' => $filtro === 'semana',
            'filtroMes' => $filtro === 'mes',
            'filtroAño' => $filtro === 'año',
            'filtroTodos' => $filtro === 'todos',
        ];

        $data['desempenoUsuarios'] = $this->model->obtenerProcentajeRespuestasCcorrectasPorUsuario($filtro);

        $data['resumen'] = [
            'usuarios' => $this->model->obtenerCantidadUsuarios($filtro),
            'partidas' => $this->model->obtenerCantidadPartidas($filtro),
            'preguntas_activas' => $this->model->obtenerCantidadPreguntas($filtro),
            'preguntas_creadas' => $this->model->obtenerCantidadPreguntasCreadas($filtro)
        ];

        $this->view->render('adminDashboard', $data);
    }

    function generarGraficoBase64($tipo, $datos, $titulo, $clave = null)
    {
        ob_start();

        if (empty($datos)) {
            $img = imagecreatetruecolor(500, 350); // Usamos el mismo tamaño estándar
            $bg = imagecolorallocate($img, 240, 240, 240);
            $textColor = imagecolorallocate($img, 50, 50, 50);
            imagefilledrectangle($img, 0, 0, 500, 350, $bg);
            imagestring($img, 5, 100, 150, "$titulo (sin datos)", $textColor);
            imagepng($img);
            imagedestroy($img);
        } else {
            if ($tipo === 'pie') {
                require_once __DIR__ . '/../libs/jpgraph/src/jpgraph.php';
                require_once __DIR__ . '/../libs/jpgraph/src/jpgraph_pie.php';
                $valores = array_column($datos, 'cantidad');
                $labels = array_column($datos, $clave);
                $graph = new PieGraph(500, 350);
                $graph->title->Set($titulo);
                $plot = new PiePlot($valores);
                $plot->SetLegends($labels);
                $graph->Add($plot);
                $graph->Stroke();
            } elseif ($tipo === 'bar') {

                require_once __DIR__ . '/../libs/jpgraph/src/jpgraph.php';
                require_once __DIR__ . '/../libs/jpgraph/src/jpgraph_bar.php';
                $valores = array_column($datos, 'cantidad');
                $labels = array_column($datos, 'grupo_etario');

                $graph = new Graph(500, 350);
                $graph->SetScale('textint');
                $graph->SetMargin(50, 30, 50, 40);
                $graph->title->Set($titulo);
                $graph->xaxis->SetTickLabels($labels);
                $graph->xaxis->SetLabelAngle(15);
                $barplot = new BarPlot($valores);
                $barplot->SetWidth(0.5);
                $barplot->SetFillColor('orange');
                $barplot->value->Show();
                $barplot->value->SetFormat('%d');
                $graph->Add($barplot);
                $graph->Stroke();
            }
        }

        return 'data:image/png;base64,' . base64_encode(ob_get_clean());
    }

    public function exportarPDF()
    {
        $filtro = $_GET['filtro_fecha'] ?? 'semana';

        // Obtener datos
        $datosPais = $this->model->obtenerUsuariosPorPais($filtro);
        $datosSexo = $this->model->obtenerUsuariosPorSexo($filtro);
        $datosGrupo = $this->model->obtenerUsuariosPorGrupoEtario($filtro);
        $datosUsuarios = $this->model->obtenerCantidadUsuarios($filtro);
        $datosPartidas = $this->model->obtenerCantidadPartidas($filtro);
        $datosPreguntasActivas = $this->model->obtenerCantidadPreguntas($filtro);
        $datosPreguntasCreadas = $this->model->obtenerCantidadPreguntasCreadas($filtro);

        // Generar gráficos válidos
        $graficoPais = $this->generarGraficoBase64('pie', $datosPais, "Jugadores por país", 'pais');
        $graficoSexo = $this->generarGraficoBase64('pie', $datosSexo, "Jugadores por sexo", 'sexo');
        $graficoGrupo = $this->generarGraficoBase64('bar', $datosGrupo, "Jugadores por grupo etario");

        // Tabla resumen para las métricas
        $htmlResumen = '
        <div class="row">
            <div class="col-full">
                <div class="chart-container">
                    <h3>Resumen de Métricas</h3>
                    <table>
                        <tr><th>Métrica</th><th>Registros totales</th><th>En el periodo seleccionado</th></tr>
                        <tr><td>Usuarios</td><td>' . ($datosUsuarios['total'] ?? 0) . '</td><td>' . ($datosUsuarios['filtrado'] ?? 0) . '</td></tr>
                        <tr><td>Partidas</td><td>' . ($datosPartidas['total'] ?? 0) . '</td><td>' . ($datosPartidas['filtrado'] ?? 0) . '</td></tr>
                        <tr><td>Preguntas Activas</td><td>' . ($datosPreguntasActivas['total'] ?? 0) . '</td><td>' . ($datosPreguntasActivas['filtrado'] ?? 0) . '</td></tr>
                        <tr><td>Preguntas Creadas</td><td>' . ($datosPreguntasCreadas['total'] ?? 0) . '</td><td>' . ($datosPreguntasCreadas['filtrado'] ?? 0) . '</td></tr>
                    </table>
                </div>
            </div>
        </div>
    ';

        // Crear PDF
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $html = '
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2, h3 { text-align: center; }

        .chart-container {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
        }

        .chart-container.summary {
            margin-bottom: 30px;
        }

        .row { width: 100%; clear: both; margin-bottom: 20px; }
        .col-left { width: 48%; float: left; }
        .col-right { width: 48%; float: right; }
        .col-full { width: 98%; margin: 0 auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th { background-color: #f2f2f2; }

        img.chart-img {
            display: block;
            margin: 0 auto;
            height: auto;
        }
        img.chart-pie {
            max-width: 110%;
        }
        img.chart-bar {
            max-width: 80%;
        }
        
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <h2>Reporte de Métricas (Filtro: ' . htmlspecialchars($filtro) . ')</h2>

    ' . $htmlResumen . '

    <div class="row">
        <div class="col-left">
            <div class="chart-container">
                <h3>Jugadores por País</h3>
                <img src="' . $graficoPais . '" class="chart-img chart-pie">
            </div>
        </div>
        <div class="col-right">
            <div class="chart-container">
                <h3>Jugadores por Sexo</h3>
                <img src="' . $graficoSexo . '" class="chart-img chart-pie">
            </div>
        </div>
    </div>

    <div style="clear: both;"></div>

    <div class="page-break"></div>
    <div class="row">
        <div class="col-full">
            <div class="chart-container">
                <h3>Jugadores por Grupo Etario</h3>
                <img src="' . $graficoGrupo . '" class="chart-img chart-bar">
            </div>
        </div>
    </div>

</body>
</html>';



        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("reporte_usuarios.pdf", ["Attachment" => false]);
    }


    public function exportarPDFDesempeno()
    {
        $filtro = $_GET['filtro_fecha'] ?? 'semana';
        $desempenoUsuarios = $this->model->obtenerProcentajeRespuestasCcorrectasPorUsuario($filtro);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $htmlTable = '<table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                        <thead>
                            <tr style="background-color: #f2f2f2;">
                                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Usuario</th>
                                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Correctas</th>
                                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Totales</th>
                                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">% Aciertos</th>
                            </tr>
                        </thead>
                        <tbody>';

        if (empty($desempenoUsuarios)) {
            $htmlTable .= '<tr><td colspan="4" style="border: 1px solid #ddd; padding: 8px; text-align: center;">No hay datos de desempeño para el filtro seleccionado.</td></tr>';
        } else {
            foreach ($desempenoUsuarios as $usuario) {
                $htmlTable .= '<tr>
                                <td style="border: 1px solid #ddd; padding: 8px;">' . htmlspecialchars($usuario['nombre_usuario']) . '</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">' . htmlspecialchars($usuario['respuestas_correctas']) . '</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">' . htmlspecialchars($usuario['total_respuestas']) . '</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">' . htmlspecialchars(number_format($usuario['porcentaje_aciertos'], 2)) . '%</td>
                            </tr>';
            }
        }

        $htmlTable .= '</tbody></table>';

        $html = '
            <html>
            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 20px;
                    }
                    h2 {
                        text-align: center;
                        margin-bottom: 30px;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 20px;
                    }
                    th, td {
                        border: 1px solid #ddd;
                        padding: 8px;
                        text-align: left;
                    }
                    th {
                        background-color: #f2f2f2;
                    }
                    tr:nth-child(even) {
                        background-color: #f9f9f9;
                    }
                </style>
            </head>
            <body>
                <h2>Reporte de Desempeño de Usuarios (Filtro: ' . htmlspecialchars($filtro) . ')</h2>
                ' . $htmlTable . '
            </body>
            </html>';


        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("reporteDesempenoUsuarios.pdf", ["Attachment" => 0]);
    }

}