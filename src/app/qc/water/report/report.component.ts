import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-report',
  templateUrl: './report.component.html',
  styleUrls: ['./report.component.css'],
  providers:[DatePipe]
})
export class ReportComponent implements OnInit {

  isView = false;
  loading = false;
  results;
  results1;
  water_type='';
  selectedPlan = [];
  from_date='';
  to_date='';
  sample_qty=0;
  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');   
   this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit() {
    this.getTestingReport();
  }

  getTestingReport() {
    this.loading = true;
    this.service.get('qc/water.php?type=getTestingReport&from_date='+this.from_date+'&to_date='+this.to_date).subscribe({
      next: (response) => {
        this.results = response;
        this.results1 = response;
        this.loading = false;
      },
      error: () => {
        this.results = [];
        this.results1 = [];
        this.loading = false;
      }
    });
  }

  view(result) {
    this.selectedPlan = result || {};
    this.sample_qty= +this.selectedPlan['chemical_qty'] + +this.selectedPlan['microbiology_qty'];
    this.isView = true;
  }

  download(){
    this.service.open('qc/water.php?type=downloadTestingARLog&from_date='+this.from_date +'&to_date='+this.to_date);
  }

  downloadReport(){
    this.service.open('qc/water.php?type=downloadTestingARReport&id='+ this.selectedPlan['id']);
  }

  filterStock(){
    this.results= [];
    for(let i=0; i<this.results1.length; i++){
      let data = this.results1[i];
      if(data.water_type.toUpperCase().includes(this.water_type.toUpperCase())){
        this.results.push(data);
      }
    }
  }

  clear(){
    this.water_type = '';
    this.filterStock();
  }

}
