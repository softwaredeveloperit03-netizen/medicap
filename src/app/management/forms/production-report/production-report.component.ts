import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as ApexCharts from 'apexcharts';

@Component({
  selector: 'app-production-report',
  templateUrl: './production-report.component.html'
})
export class ProductionReportComponent implements OnInit {

  dosages;
  dosage_form = '';
  from_date = '';
  to_date = '';
  results;
  selectedProducts=[];
  products='';
  product_code='';
  status = 'complete';
  constructor(private service: DataAccessService) {
    var d = new Date();
    let day = d.getDate();
    let m = d.getMonth();
    m = +m + 1;
    let mon = "";
    if (day > 0 && day < 10) {
      mon = "0" + day;
    }
    if (m > 0 && m < 10) {
      mon = "0" + m;
    }
    this.to_date = d.getFullYear() + "-" + mon + "-" + day;
    this.from_date = d.getFullYear() + "-" + mon + "-01";
  }

  ngOnInit() {
    this.getDosageForm();
    this.getcompletedBatches();
    // this.getChartData();
  }

  getDosageForm() {
    this.service.get('production/report.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }

  getSelectedProducts(index) {
    index = index - 1;
    this.selectedProducts = this.dosages[index];
  }
  
  getcompletedBatches() {
    this.service.get('production/report.php?type=getCompletedBatches&dosage_form='+ this.dosage_form +'&from_date=' +this.from_date +'&to_date=' +this.to_date +'&product_code=' + this.product_code + '&status=' + this.status).subscribe(response => {
      this.results = response;
    });
  }

  getChartData() {
    this.service.get('production/report.php?type=getFinishGoodsChartData').subscribe(response => {
      this.results = response;

      let htmlcode = "";
      for (let i = 0; i < this.results.length; i++) {
        let j = +i + 1;
        htmlcode += '<div class="clr-col-lg-6 clr-col-md-6 clr-col-sm-12 clr-col-xs-12"><div class="card"><div class="card-header">'+ this.results[i].product_name +'</div><div class="card-block"><div class="card-text" id="chart'+ j +'"></div></div></div></div>';
      }

      document.querySelector("#charts").innerHTML = htmlcode;

      for (let i = 0; i < this.results.length; i++) {
        let j = +i + 1;
        let data = this.results[i].data;

        let series = [];
        let labels = [];

        let heights = 0;
        for (let i = 0; i < data.length; i++) {
          let result = data[i];
          
          series[series.length] = result['qty'];
          labels[labels.length] = result['month'];
          if (+result['qty'] > heights) {
            heights = +result['qty'];
          }
        }

        let chartOptions = {
          series: [
            {
              name: "basic",
              data: series
            }
          ],
          chart: {
            type: "bar",
            height: heights
          },
          plotOptions: {
            bar: {
              horizontal: true
            }
          },
          dataLabels: {
            enabled: true
          },
          xaxis: {
            categories: labels
          }
        };

        let chart1 = new ApexCharts(document.querySelector("#chart" + j), chartOptions);
        chart1.render();
      }

    });
  }

}

