import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-coa',
  templateUrl: './coa.component.html',
  styleUrls: ['./coa.component.css']
})
export class CoaComponent implements OnInit {

  results;

  constructor(private service: DataAccessService) {
  }

  ngOnInit() {
    this.getTestings();
  }

  getTestings() {
    this.service.get('qc/testing/packing.php?type=getTestingReport').subscribe(response => {
      this.results = response;
    });
  }

  viewCOA(testing_no) {
    this.service.open('pdf1/coa.php?type=coa&testing_no=' + testing_no);
  }
}
