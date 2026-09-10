import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-request',
  templateUrl: './request.component.html',
  styleUrls: ['./request.component.css']
})
export class RequestComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  url = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getApprovedDeptSOPs();
  }

  getApprovedDeptSOPs() {
    this.service.get('sops.php?type=getApprovedDeptSOPs').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.url = this.service.url + 'pdf1/sop.php?type=sopdigital&sop_no=' + this.selectedResult['sop_no'] + '&token=' + localStorage.getItem('token');
    this.isView = true;
  }

  revise(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['sop_no'] = this.selectedResult['sop_no'];
    this.service.post('sops.php?type=reviseRequest', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.isView = false;
        alert('SOP Revise Request send successfully');
        this.getApprovedDeptSOPs();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
