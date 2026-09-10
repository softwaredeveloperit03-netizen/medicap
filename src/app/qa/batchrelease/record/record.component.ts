import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-record',
  templateUrl: './record.component.html'
})
export class BatchReleaseRecordComponent implements OnInit {
  isView = false;
  results = [];
  fromdate;
  todate;

  selectedResult = [];
  constructor(private service: DataAccessService) {
  }
  ngOnInit() {
    this.getbatchrelease();
  }

  getbatchrelease() {
    this.service.get('batch-release.php?type=getbatchrelease').subscribe((response:any) => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  printchecking(value, mode){
    if(mode == 'manual'){
      this.service.open('pdf1/batch-release.php?type=checkingrecord&id='+value);
    }else{
      this.service.open('pdf1/batch-release.php?type=checkingrecorddigital&id='+value);
    }
    
  }
  printcertificate(value,mode){
    if(mode == 'manual'){
      this.service.open('pdf1/batch-release.php?type=certificate&id='+value);
    }else{
      this.service.open('pdf1/batch-release.php?type=certificatedigital&id='+value);
    }
  }
  printreport(){
    this.service.open('pdf1/batch-release.php?type=printrecord');
  }
}