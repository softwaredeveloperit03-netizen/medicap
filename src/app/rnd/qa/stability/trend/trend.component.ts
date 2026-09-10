import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import * as ApexCharts from 'apexcharts';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-trend',
  templateUrl: './trend.component.html',
  styleUrls: ['./trend.component.css']
})
export class TrendComponent implements OnInit {
  options;
  chart;
  options1;
  chart1;
  constructor(private service: DataAccessService , private router:Router) {
  }

  ngOnInit(): void {
    this.ChemberChart();
    this.ConditionChart();
  }
  onClose(){
    this.router.navigate(['/rnd/qa/stability']);
  }
  ChemberChart(){
    this.service.get('qa/stability.php?type=StabilityChembersChart').subscribe((response:any) =>{
      this.options = {
        series: response['series'],
        labels: response['lables'],
        chart: { type: 'pie' },
      };
      this.chart = new ApexCharts(document.querySelector("#chart"), this.options);
      this.chart.render();
    })
  }
  ConditionChart(){
    this.service.get('qa/stability.php?type=StabilityConditionChart').subscribe((response:any) =>{
      this.options1 = {
        series: response['series'],
        labels: response['lables'],
        chart: { type: 'pie' },
      };
      this.chart1 = new ApexCharts(document.querySelector("#chart1"), this.options1);
      this.chart1.render();
    })
  }

}
