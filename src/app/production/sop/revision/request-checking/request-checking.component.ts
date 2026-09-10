import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-request-checking',
  templateUrl: './request-checking.component.html',
  styleUrls: ['./request-checking.component.css']
})
export class RequestCheckingComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  url = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getRevisionRequestLog();
  }

  getRevisionRequestLog() {
    this.service.get('sops.php?type=getPendingRevisionRequests').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.url = this.service.url + 'pdf1/sop.php?type=sopdigital&sop_no=' + this.selectedResult['sop_no'] + '&token=' + localStorage.getItem('token');
    this.isView = true;
  }

  update(status, data) {
    if (!data.valid) {
      alert('An error occured, please try again!');
      return;
    }
    let temp = data.value;
    temp['sop_no'] = this.selectedResult['sop_no'];
    temp['status'] = status;
    this.service.post('sops.php?type=checkSOPRevisionRequest', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Updated Successfully');
        this.isView = false;
        this.getRevisionRequestLog();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
