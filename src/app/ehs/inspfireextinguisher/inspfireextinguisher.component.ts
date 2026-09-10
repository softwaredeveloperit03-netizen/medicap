import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-inspfireextinguisher',
  templateUrl: './inspfireextinguisher.component.html',
  styleUrls: ['./inspfireextinguisher.component.css'],
  providers: [DatePipe],
})
export class InspfireextinguisherComponent implements OnInit {
  ComeeteeList;

  constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.GetInspectionRecord();
  }

  GetInspectionRecord() {
    this.service
      .get('ehs/inspfireextinguisher.php?type=GetInspectionRecord')
      .subscribe((response) => {
        this.ComeeteeList = response;
      });
  }

  downloadPdf() {
    this.service.open('ehs/inspfireextinguisher.php?type=downloadInspectionLogPdf');
  }
}
