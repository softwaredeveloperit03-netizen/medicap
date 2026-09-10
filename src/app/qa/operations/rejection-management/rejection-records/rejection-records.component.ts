import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-rejection-records',
  templateUrl: './rejection-records.component.html',
  styleUrls: ['./rejection-records.component.css']
})
export class RejectionRecordsComponent implements OnInit {

  results;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
  }

}
