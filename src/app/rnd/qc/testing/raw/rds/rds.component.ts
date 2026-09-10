import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-rds',
  templateUrl: './rds.component.html',
  styleUrls: ['./rds.component.css']
})
export class RdsComponent implements OnInit {
  results;
  selectedReport = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getARReport();
  }

  getARReport() {
    this.service.get('qc/testing/raw.php?type=getTestingReport').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    let url = this.service.url + 'pdf1/rds.php?testing_no=' + this.selectedReport['testing_no'] + '&token=' + localStorage.getItem('token');
    window.open(url, '_blank');
  }

}
