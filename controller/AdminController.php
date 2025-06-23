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

        $this->view->render('adminDashboard', $data);
    }

    function generarGraficoBase64($tipo, $datos, $titulo, $clave = null)
    {
        ob_start();

        if (empty($datos)) {
            $img = imagecreatetruecolor(400, 300);
            $bg = imagecolorallocate($img, 240, 240, 240);
            $textColor = imagecolorallocate($img, 50, 50, 50);
            imagefilledrectangle($img, 0, 0, 400, 300, $bg);
            imagestring($img, 5, 100, 130, "$titulo (sin datos)", $textColor);
            imagepng($img);
            imagedestroy($img);
        } else {
            if ($tipo === 'pie') {
                require_once __DIR__ . '/../libs/jpgraph/src/jpgraph.php';
                require_once __DIR__ . '/../libs/jpgraph/src/jpgraph_pie.php';
                $valores = array_column($datos, 'cantidad');
                $labels = array_column($datos, $clave);
                $graph = new PieGraph(400, 300);
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
                $graph = new Graph(400, 300);
                $graph->SetScale('textint');
                $graph->xaxis->SetTickLabels($labels);
                $graph->title->Set($titulo);
                $barplot = new BarPlot($valores);
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

        // Generar gráficos en base64
        $graficoPais = $this->generarGraficoBase64('pie', $datosPais, "Jugadores por país", 'pais');
        $graficoSexo = $this->generarGraficoBase64('pie', $datosSexo, "Jugadores por sexo", 'sexo');
        $graficoGrupo = $this->generarGraficoBase64('bar', $datosGrupo, "Jugadores por grupo etario");

        // Crear PDF
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $html = '
            <html>
            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        background-color: #ffffff;
                        margin: 20px;
                    }
                    h2 {
                        text-align: center;
                        margin-bottom: 30px;
                    }
                    table {
                        width: 100%;
                        border-spacing: 15px;
                    }
                    td {
                        width: 50%;
                        vertical-align: top;
                        text-align: center;
                        background-color: #ffffff;
                        border: 1px solid #cccccc;
                        border-radius: 8px;
                        padding: 10px;
                    }
                    .full-width {
                        width: 100%;
                    }
                    img {
                        width: 100%;
                        height: auto;
                    }
                    h3 {
                        margin-bottom: 10px;
                    }
                </style>
            </head>
            <body>
                <h2>Reporte de Usuarios (Filtro: ' . htmlspecialchars($filtro) . ')</h2>
            
                <table>
                    <tr>
                        <td>
                            <h3>Jugadores por País</h3>
                            <img src="' . $graficoPais . '" />
                        </td>
                        <td>
                            <h3>Jugadores por Sexo</h3>
                            <img src="' . $graficoSexo . '" />
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="full-width">
                            <h3>Jugadores por Grupo Etario</h3>
                            <img src="' . $graficoGrupo . '" />
                        </td>
                    </tr>
                </table>
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