import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from  '@angular/router';
import {  DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-biomedwaste',
  templateUrl: './biomedwaste.component.html',
  styleUrls: ['./biomedwaste.component.css'],
  providers: [DatePipe]
})
export class BiomedwasteComponent implements OnInit {

  // disposaldt;
  // category;
  // ttlWeight;
  date;
  BioMedWaste;
  isNew = false;
  bioMedArrray = [];
  selectedIndex = [];

  constructor(
    private service: DataAccessService, 
    private router: Router, 
    private datePipe: DatePipe
  ) {
    this.date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getBioMedWaste();
    this.getEmployeeQualityControl();
  }

  bioMedWaste
  getBioMedWaste(){
    this.service.get('biomedicalwaste.php?type=getBioMedWaste').subscribe(response => {
      this.bioMedWaste = response
    });
  }

  emps;
    getEmployeeQualityControl() {
    this.service.get('deptEmployee.php?type=getEmployeeQualityControl')
      .subscribe((response) => {
        this.emps = response;
      });
  }

  getData(i){
    this.selectedIndex = this.emps[i-1];
  }
  
  saveBioMedWaste(data){
    let temp = data.value;
    console.log(temp);
    this.service.post('ehs/biomedicalwaste.php?type=saveBioMedicalWaste', JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Bio Medical Waste Saved Successfully');
        this.isNew = false;
        this.getBioMedWaste();
      } else {
        alertify.error(response['status']);
      }
    })
  
  }

}
