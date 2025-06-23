<?php

class GraphController
{
    private $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function usuariosPorPais()
    {
        $filtro = $_GET['filtro_fecha'] ?? 'semana';
        $datos = $this->model->obtenerUsuariosPorPais($filtro);
        $this->generarGraficoTorta($datos, "Jugadores por país", 'pais');
    }

    public function usuariosPorSexo()
    {
        $filtro = $_GET['filtro_fecha'] ?? 'semana';
        $datos = $this->model->obtenerUsuariosPorSexo($filtro);
        $this->generarGraficoTorta($datos, "Jugadores por sexo", 'sexo');
    }

    public function usuariosPorGrupo()
    {
        $filtro = $_GET['filtro_fecha'] ?? 'semana';
        $datos = $this->model->obtenerUsuariosPorGrupoEtario($filtro);
        $this->generarGraficoBarra($datos, "Jugadores por grupo etario");
    }

    public function cantidadDeUsuarios()
    {
        $filtro = $_GET['filtro_fecha'] ?? 'semana';
        $datos = $this->model->obtenerCantidadUsuarios($filtro);

        // se hace así para que haga un array de arrays y el gráfico pueda interpretarlo correctamente
        // la "label" se llama "grupo_etario" porque estaba así
        // habría que cambiar el array que crea el metodo de grupo etario
        $datosParaGrafico = [
            ['cantidad' => $datos['total'] ?? 0, 'grupo_etario' => 'Total'],
            ['cantidad' => $datos['filtrado'] ?? 0, 'grupo_etario' => 'Periodo']
        ];

        $this->generarGraficoBarra($datosParaGrafico, "Comparativa de Usuarios");
    }

    public function cantidadDePartidas()
    {
        $filtro = $_GET['filtro_fecha'] ?? 'semana';
        $datos = $this->model->obtenerCantidadPartidas($filtro);

        // se hace así para que haga un array de arrays y el gráfico pueda interpretarlo correctamente
        $datosParaGrafico = [
            ['cantidad' => $datos['total'] ?? 0, 'grupo_etario' => 'Total'],
            ['cantidad' => $datos['filtrado'] ?? 0, 'grupo_etario' => 'Periodo']
        ];

        $this->generarGraficoBarra($datosParaGrafico, "Comparativa de Partidas");
    }

    public function cantidadDePreguntas()
    {
        $filtro = $_GET['filtro_fecha'] ?? 'semana';
        $datos = $this->model->obtenerCantidadPreguntas($filtro);

        // se hace así para que haga un array de arrays y el gráfico pueda interpretarlo correctamente
        $datosParaGrafico = [
            ['cantidad' => $datos['total'] ?? 0, 'grupo_etario' => 'Total'],
            ['cantidad' => $datos['filtrado'] ?? 0, 'grupo_etario' => 'Periodo']
        ];

        $this->generarGraficoBarra($datosParaGrafico, "Comparativa Preguntas Activas");
    }

    public function cantidadDePreguntasCreadas()
    {
        $filtro = $_GET['filtro_fecha'] ?? 'semana';
        $datos = $this->model->obtenerCantidadPreguntasCreadas($filtro);

        // se hace así para que haga un array de arrays y el gráfico pueda interpretarlo correctamente
        $datosParaGrafico = [
            ['cantidad' => $datos['total'] ?? 0, 'grupo_etario' => 'Total'],
            ['cantidad' => $datos['filtrado'] ?? 0, 'grupo_etario' => 'Periodo']
        ];

        $this->generarGraficoBarra($datosParaGrafico, "Comparativa Preguntas Creadas");
    }

    private function generarGraficoTorta($datos, $titulo, $clave)
    {
        require_once __DIR__ . '/../libs/jpgraph/src/jpgraph.php';
        require_once __DIR__ . '/../libs/jpgraph/src/jpgraph_pie.php';

        header("Content-Type: image/png");

        if (empty($datos)) {
            $this->crearGraficoVacio($titulo);
            return;
        }

        $valores = array_column($datos, 'cantidad');
        $labels = array_column($datos, $clave);

        $graph = new PieGraph(400, 300);
        $graph->title->Set($titulo);

        $plot = new PiePlot($valores);
        $plot->SetLegends($labels);
        $graph->Add($plot);
        $graph->Stroke();
    }

    private function generarGraficoBarra($datos, $titulo)
    {
        require_once __DIR__ . '/../libs/jpgraph/src/jpgraph.php';
        require_once __DIR__ . '/../libs/jpgraph/src/jpgraph_bar.php';

        header("Content-Type: image/png");

        if (empty($datos)) {
            $this->crearGraficoVacio($titulo);
            return;
        }

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

    private function crearGraficoVacio($mensaje)
    {
        $img = imagecreatetruecolor(400, 300);
        $bg = imagecolorallocate($img, 240, 240, 240);
        $textColor = imagecolorallocate($img, 50, 50, 50);
        imagefilledrectangle($img, 0, 0, 400, 300, $bg);
        imagestring($img, 5, 10, 130, $mensaje . " (sin datos)", $textColor);
        imagepng($img);
        imagedestroy($img);
    }
}