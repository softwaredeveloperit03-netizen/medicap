import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import * as ApexCharts from 'apexcharts';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-yield',
  templateUrl: './yield.component.html',
  styleUrls: ['./yield.component.css'],
  providers:[DatePipe]
})
export class YieldComponent implements OnInit {

  results;
  options;
  chart;
  from_date = '';
  to_date = '';

  constructor(private service:DataAccessService,private datePipe:DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
   }

  ngOnInit(): void {
    this.getYields();
   
  }

  getYields(){
    this.service.get('qa/oot.php?type=getYields&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe((response:any) => {
      this.results = response;
      this.options = {
        series: [{
          name: "Yield Percentage",
          data: response.yield,
        }],
        chart: {
        height: 350,
        type: 'line',
        zoom: { enabled: false }
       },
      dataLabels: { enabled: false },
      stroke: { curve: 'straight'  },
      title: { text: 'Yield Percentage By BMR No', align: 'left' },
      grid: {
        row: {
          colors: ['#f3f3f3', 'transparent'], 
          opacity: 0.5
        },
      },
      xaxis: { categories: response.data, }
      };
      this.chart = new ApexCharts(document.querySelector("#chart"), this.options);
      this.chart.render();
    });
  }

}
