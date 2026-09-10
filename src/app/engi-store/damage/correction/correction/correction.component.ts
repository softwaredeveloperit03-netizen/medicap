 import { Component, OnInit } from '@angular/core';
 import { DataAccessService } from 'src/app/data-access.service';
 declare let alertify;


@Component({
  selector: 'app-correction',
  templateUrl: './correction.component.html',
  styleUrls: ['./correction.component.css']
})
export class CorrectionComponent implements OnInit {

  isView = false;
  results;
  selectedReport;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getRejecection();
  }


  getRejecection() {
    this.service.get('store/challan.php?type=getRejection').subscribe(response => {
      this.results = response;
    })
  }
  view(index){
    this.selectedReport=this.results[index];
    this.isView=true;
  }
  update(pending,data) {
    if (!data.valid) {
      alertify.error('All fields are required')
      return;
    }
    let temp = data.value;
    temp['materials'] = this.selectedReport['materials'];
    this.service.post('store/challan.php?type=updateRejectionPO&id=' + this.selectedReport['id'] +'&status=' +this.selectedReport['status'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getRejecection();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
