import { Component, OnInit } from '@angular/core';
import * as CanvasJS from 'src/assets/js/canvasjs.min';

@Component({
  selector: 'app-trend',
  templateUrl: './trend.component.html',
  styleUrls: ['./trend.component.css']
})
export class TrendComponent implements OnInit {

  constructor() { }

  ngOnInit(): void {
    const chart = new CanvasJS.Chart('chartContainer', {
      theme: 'light2',
      animationEnabled: true,
      exportEnabled: true,
      title: {
        text: 'Department'
      },
      data: [{
        type: 'pie',
        showInLegend: true,
        toolTipContent: '<b>{name}</b>: ${y} (#percent%)',
        indexLabel: '{name} - #percent%',
        dataPoints: [
          { y: 120, name: 'QC'},
          { y: 120, name: 'QA'},
          { y: 120, name: 'Production'},
          { y: 120, name: 'IPQA' },
          { y: 120, name: 'R&D' },
          { y: 120, name: 'Store' },
          { y: 120, name: 'Microbiology' },
          { y: 120, name: 'HR' },
          { y: 120, name: 'Vendor' },
          { y: 120, name: 'Packing' },
          { y: 120, name: 'Admin' },
        ]
      }]
    });
    chart.render();


    const chart1 = new CanvasJS.Chart('chartContainer1', {
      theme: 'light2',
      animationEnabled: true,
      exportEnabled: true,
      title: {
        text: 'Incident Trends'
      },
      data: [{
        type: 'pie',
        showInLegend: true,
        toolTipContent: '<b>{name}</b>: ${y} (#percent%)',
        indexLabel: '{name} - #percent%',
        dataPoints: [
          { y: 120, name: 'Critical'},
          { y: 120, name: 'Minor'},
          { y: 120, name: 'Major'},
        ]
      }]
    });
    chart1.render();


    const chart2 = new CanvasJS.Chart('chartContainer2', {
      animationEnabled: true,
      exportEnabled: true,
      title: {
        text: 'Incident Log'
      },
      data: [{
        type: 'column',
        dataPoints: [
          { y: 10, label: 'Jan' },
          { y: 20, label: 'Feb' },
          { y: 30, label: 'Mar'},
          { y: 35, label: 'Apr' },
          { y: 40, label: 'May' },
          { y: 50, label: 'Jun' },
          { y: 60, label: 'July' },
          { y: 66, label: 'Aug' },
          { y: 70, label: 'Sep' },
          { y: 72, label: 'Act' },
          { y: 80, label: 'Nov' },
          { y: 90, label: 'Dec' }
        ]
      }]
    });

    chart2.render();
  }

}
