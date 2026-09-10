import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-deviation-approval',
  templateUrl: './deviation-approval.component.html'
})
export class DeviationApprovalComponent implements OnInit {
  
  isView = false;
  results;

  selectedDev = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getCheckedDeviations();
  }

  getCheckedDeviations() {
    this.service.get('deviation.php?type=getCheckedDeviations').subscribe(response => {
      this.results = response;
    });
  }

  viewDeviation(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }

  save(data, status) {
    if (!data.valid) {
      alert('An error occured, please try again!');
      return;
    }
    let temp = data.value;
    temp['dev_no'] = this.selectedDev['dev_no'];
    temp['status'] = status;
    temp['department'] = this.selectedDev['department'];
    temp['related_to'] = this.selectedDev['related_to'];
    this.service.post('deviation.php?type=saveQAApproval&id=' + this.selectedDev['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status']) {
        alert("Deviation Updated Successfully");
        this.isView = false;
        this.getCheckedDeviations();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
