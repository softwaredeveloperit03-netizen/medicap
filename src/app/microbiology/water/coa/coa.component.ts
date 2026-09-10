import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-coa',
  templateUrl: './coa.component.html',
  styleUrls: ['./coa.component.css'],
  providers:[DatePipe]
})
export class COAComponent implements OnInit {

  isView = false;
  results;
  selectedPlan = [];
  from_date='';
  to_date='';
  sample_qty=0;
  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
    this.from_date=this.datePipe.transform(Date.now(),'dd-MM-yyyy');   
   this.to_date=this.datePipe.transform(Date.now(),'dd-MM-yyyy');   }

  ngOnInit() {
    this.getTestingReport();
  }

  getTestingReport() {
    this.service.get('qc/water.php?type=getTestingReport&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedPlan = this.results[index];
    this.sample_qty= +this.selectedPlan['chemical_qty'] + +this.selectedPlan['microbiology_qty'];
    this.isView = true;
  }

  download(){
    this.service.open('qc/water.php?type=downloadTestingCOALog&from_date='+this.from_date +'&to_date='+this.to_date);
  }

  downloadReport(){
    this.service.open('qc/water.php?type=downloadCOATestingReport&id='+ this.selectedPlan['id']);
  }

}
