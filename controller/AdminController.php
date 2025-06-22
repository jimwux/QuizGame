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

}