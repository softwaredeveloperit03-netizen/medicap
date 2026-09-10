import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  url = '';
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getCheckedSOPs();
  }

  getCheckedSOPs() {
    this.service.get('sops.php?type=getSOPDeptLog').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.url = this.service.url + 'pdf1/sop.php?type=sopdigital&sop_no=' + this.selectedResult['sop_no'] + '&token=' + localStorage.getItem('token');
    this.isView = true;
  }

  download(value) {
    if (value == 'manual') {
      this.service.open('pdf1/sop.php?type=sop&sop_no=' + this.selectedResult['sop_no']);
    } else if (value == 'digital') {
      this.service.open('pdf1/sop.php?type=sopdigital&sop_no=' + this.selectedResult['sop_no']);
    } else {
      this.service.open('pdf1/sop.php?type=log');
    }
  }

}
