import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-qa',
  templateUrl: './qa.component.html',
  styleUrls: ['./qa.component.css'],
  providers:[DatePipe]
})
export class QaComponent implements OnInit {

  isView = false;
  results = [];
  fromdate;
  todate;

  selectedResult = []; 
  from_date = '';
  to_date = '';
  product_type='';
  dosages;
  company;
  company_unit='';

  constructor(private service: DataAccessService, private router :Router,
    private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }
 
  ngOnInit() {
    this.getbatchrelease();
    this.getDosages();
    this.getCompanyUnits();
  }

  getbatchrelease() {
    this.service.get('ipqc/finish.php?type=getBatchReleaseLog&product_type=' + this.product_type + '&from_date=' + this.from_date + '&to_date='+this.to_date +'&company_unit='+this.company_unit).subscribe((response:any) => {
      this.results = response;
    });
  }
  getDosages(){
    this.service.get('common.php?type=getDosages').subscribe(response=>{
      this.dosages=response;
    });
  }

  getCompanyUnits(){
    this.service.get('common.php?type=getCompanyUnits').subscribe(response=>{
      this.company=response;
    });
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  printchecking(value, mode){
    if(mode == 'manual'){
      this.service.open('ipqc/finish.php?type=downloadBatchReleaseChecking&id=' + value);
      // this.service.open('pdf1/batch-release.php?type=checkingrecord&id='+value);
    }else{
      // this.service.open('pdf1/batch-release.php?type=checkingrecorddigital&id='+value);
    }
    
  }
  printcertificate(value,mode){
    if(mode == 'manual'){
      this.service.open('ipqc/finish.php?type=downloadBatchReleaseCertificate&id=' + value);

      // this.service.open('pdf1/batch-release.php?type=certificate&id='+value);
    }else{
      // this.service.open('pdf1/batch-release.php?type=certificatedigital&id='+value);
    }
  }
  printreport(){
    this.service.open('ipqc/finish.php?type=downloadBatchReleaseLog&product_type=' + this.product_type + '&from_date=' + this.from_date + '&to_date='+this.to_date +'&company_unit='+this.company_unit);
   }
}