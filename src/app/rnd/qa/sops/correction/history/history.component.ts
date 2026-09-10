import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-history',
  templateUrl: './history.component.html',
  styleUrls: ['./history.component.css']
})
export class HistoryComponent implements OnInit {

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
}
