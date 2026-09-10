import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
  }

}
