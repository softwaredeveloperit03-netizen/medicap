import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  selectedResult=[];
  results;
  isView = false;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }
}
