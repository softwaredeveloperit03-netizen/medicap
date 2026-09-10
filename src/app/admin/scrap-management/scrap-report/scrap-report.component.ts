import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-scrap-report',
  templateUrl: './scrap-report.component.html',
  styleUrls: ['./scrap-report.component.css']
})
export class ScrapReportComponent implements OnInit {

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getDetails();
  }

data;
getDetails()
{
  this.service.get('qa/all2.php?type=getScrapReport').subscribe((response:any) => {
    this.data = response;
   
  });
}

}
