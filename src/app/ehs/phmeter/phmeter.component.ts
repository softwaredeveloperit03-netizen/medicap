import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-phmeter',
  templateUrl: './phmeter.component.html',
  styleUrls: ['./phmeter.component.css'],
  providers: [DatePipe]
})
export class PhmeterComponent implements OnInit {

  date;
  isNew = false;
  selectedphMeter = [];
  
  constructor(
    private service: DataAccessService, 
    private router: Router, 
    private datePipe: DatePipe
  ) {
    this.date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getphMeterRecord();

  }

  phMeter;
  getphMeterRecord(){
      this.service.get('ehs/phmeter.php?type=getphMeter').subscribe(response =>{
        this.phMeter = response
      });  
  }
  
  calibration;
  getCalibration(){
    this.service.get('ehs/phmeter.php?type=getCalibrationRecord').subscribe(response =>{
      this.calibration = response
    })
  }

  savephMeter(data){
  let temp = data.value;
  console.log("Submitting: ", temp);
  this.service.post("ehs/phmeter.php?type=savePhMeter", JSON.stringify(temp))
    .subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Record Saved Successfully');
        this.isNew = false;
        this.getphMeterRecord();
      } else {
        alertify.error(response['status']);
      }
    });
}


}
