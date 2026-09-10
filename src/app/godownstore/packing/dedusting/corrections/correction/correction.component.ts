import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-correction',
  templateUrl: './correction.component.html',
  styleUrls: ['./correction.component.css']
})
export class CorrectionComponent implements OnInit {

  results;
  selectedResult = [];
  isView = false;
  po_no='';
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getRejectedChallans();
  }

  getRejectedChallans() {
    this.service.get('store/challan.php?type=getRejectedChallans').subscribe(response => {
      this.results = response;
    })
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  update(data) {
    if (!data.valid) {
      alertify.error('All fields are required')
      return;
    }
    let temp = data.value;
    temp['materials'] = this.selectedResult['materials'];
    this.service.post('store/challan.php?type=updateRejectedPO&id=' + this.selectedResult['id'] +'&po_no=' +this.selectedResult['po_no'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getRejectedChallans();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  number(value){
    if (isNaN(value)){
      alertify.error('Number 10 digit Only');
      return false;
    }
  }
}

