import { Component, OnInit } from '@angular/core';
import * as ApexCharts from 'apexcharts';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-process',
  templateUrl: './process.component.html',
  styleUrls: ['./process.component.css']
})
export class ProcessComponent implements OnInit {

  products;

  results = [];
  chart;
  options;
  constructor(private service: DataAccessService) {
    
  }

  ngOnInit(): void {
    this.getProducts();
    this.options = {
      series: [{
        name: "Desktops",
        data: [10, 41, 35, 51, 49, 62, 69, 91, 148]
    }],
      chart: {
      height: 350,
      type: 'line',
      zoom: {
        enabled: false
      }
    },
    dataLabels: {
      enabled: false
    },
    stroke: {
      curve: 'straight'
    },
    title: {
      text: 'Product Trends by Month',
      align: 'left'
    },
    grid: {
      row: {
        colors: ['#f3f3f3', 'transparent'], // takes an array which will be repeated on columns
        opacity: 0.5
      },
    },
    xaxis: {
      categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
    }
    };
    this.chart = new ApexCharts(document.querySelector("#chart"), this.options);
    this.chart.render();
  }

  getProducts() {
    this.service.get('qa/apqr.php?type=getProducts').subscribe(response => {
      this.products = response;
    });
  }

  selectProduct(index) {
    index = index - 1;
    if (index !== -1) {
      this.results = this.products[index];
    }    
  }

}
