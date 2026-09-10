import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  results;
  isView=false;
  
  selectedResult = [];

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingCalibrationIdentifications();
  }
  getPendingCalibrationIdentifications(){
    this.service.get('qa/calibration.php?type=getPendingCalibrationIdentifications').subscribe(response=>{
      this.results=response;
    })
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }


  action(status){
    this.service.get('qa/calibration.php?type=checkCalibrationIdentification&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response=>{
      if (response['status'] == 'success') {
        alert('Updated Successfully');
        this.isView = false;
        this.getPendingCalibrationIdentifications();
      } else {
        alert('Failed: An error occured, please try again!');
      }
      
    })
  }

}
