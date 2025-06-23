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

        $datosUsuariosParaGrafico = [
            // acá es de nuevo el problema con la label, igual que en graphController
            ['cantidad' => $datosUsuarios['total'] ?? 0, 'grupo_etario' => 'Total'],
            ['cantidad' => $datosUsuarios['filtrado'] ?? 0, 'grupo_etario' => 'Periodo']
        ];
        $datosPartidasParaGrafico = [
            ['cantidad' => $datosPartidas['total'] ?? 0, 'grupo_etario' => 'Total'],
            ['cantidad' => $datosPartidas['filtrado'] ?? 0, 'grupo_etario' => 'Periodo']
        ];
        $datosPreguntasActivasParaGrafico = [
            ['cantidad' => $datosPreguntasActivas['total'] ?? 0, 'grupo_etario' => 'Total'],
            ['cantidad' => $datosPreguntasActivas['filtrado'] ?? 0, 'grupo_etario' => 'Periodo']
        ];
        $datosPreguntasCreadasParaGrafico = [
            ['cantidad' => $datosPreguntasCreadas['total'] ?? 0, 'grupo_etario' => 'Total'],
            ['cantidad' => $datosPreguntasCreadas['filtrado'] ?? 0, 'grupo_etario' => 'Periodo']
        ];


        // Generar gráficos en base64
        $graficoPais = $this->generarGraficoBase64('pie', $datosPais, "Jugadores por país", 'pais');
        $graficoSexo = $this->generarGraficoBase64('pie', $datosSexo, "Jugadores por sexo", 'sexo');
        $graficoGrupo = $this->generarGraficoBase64('bar', $datosGrupo, "Jugadores por grupo etario");
        $graficoUsuarios = $this->generarGraficoBase64('bar', $datosUsuariosParaGrafico, "Comparativa de Usuarios");
        $graficoPartidas = $this->generarGraficoBase64('bar', $datosPartidasParaGrafico, "Comparativa de Partidas");
        $graficoPreguntasActivas = $this->generarGraficoBase64('bar', $datosPreguntasActivasParaGrafico, "Comparativa Preguntas Activas");
        $graficoPreguntasCreadas = $this->generarGraficoBase64('bar', $datosPreguntasCreadasParaGrafico, "Comparativa Preguntas Creadas");
        // Crear PDF
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $html = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h2 { text-align: center; margin-bottom: 25px; width: 100%; }
            h3 { text-align: center; margin-bottom: 15px; }
            
            /* contenedor para cada gráfico individual */
            .chart-container {
                border: 1px solid #ccc;
                border-radius: 8px;
                padding: 15px;
                text-align: center;
            }

            /* contenedor para una fila de gráficos */
            .row {
                width: 100%;
                margin-bottom: 20px;
                clear: both;
            }

             /* columna para que los gráficos sean 2 por fila*/
            .col-left {
                width: 48%;
                float: left;
            }
            .col-right {
                width: 48%;
                float: right;
            }
            
            /* columna para que el los gráficos sean 1 por fila (centrado) */
            .col-full {
                width: 98%;
                margin: 0 auto;
            }

            img { max-width: 100%; height: auto; }
        </style>
    </head>
    <body>
        <h2>Reporte de Métricas (Filtro: ' . htmlspecialchars($filtro) . ')</h2>

        <div class="row">
            <div class="col-left">
                <div class="chart-container">
                    <h3>Jugadores por País</h3>
                    <img src="' . $graficoPais . '">
                </div>
            </div>
            <div class="col-right">
                <div class="chart-container">
                    <h3>Jugadores por Sexo</h3>
                    <img src="' . $graficoSexo . '">
                </div>
            </div>
        </div>
        <div style="clear: both;"></div>

        <div class="row">
            <div class="col-left">
                <div class="chart-container">
                    <h3>Jugadores por Grupo Etario</h3>
                    <img src="' . $graficoGrupo . '">
                </div>
            </div>
            <div class="col-right">
                <div class="chart-container">
                    <h3>Comparativa de Usuarios</h3>
                    <img src="' . $graficoUsuarios . '">
                </div>
            </div>
        </div>
        <div style="clear: both;"></div>

        <div class="row">
            <div class="col-left">
                <div class="chart-container">
                    <h3>Comparativa de Partidas</h3>
                    <img src="' . $graficoPartidas . '">
                </div>
            </div>
            <div class="col-right">
                <div class="chart-container">
                    <h3>Comparativa Preguntas Activas</h3>
                    <img src="' . $graficoPreguntasActivas . '">
                </div>
            </div>
        </div>
        <div style="clear: both;"></div>

        <div class="row">
            <div class="col-full">
                <div class="chart-container">
                    <h3>Comparativa Preguntas Creadas</h3>
                    <img src="' . $graficoPreguntasCreadas . '">
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

}