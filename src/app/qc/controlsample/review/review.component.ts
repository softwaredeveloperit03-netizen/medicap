import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-review',
  templateUrl: './review.component.html',
  styleUrls: ['./review.component.css']
})
export class ReviewComponent implements OnInit {

  isView = false;
  results;
  fromdate;
  todate;
  material_type ='';

  selectedResult = [];
  constructor(private service: DataAccessService,private router: Router ) {}

  ngOnInit() {
   //this.getReviewsLog();
  }

  getReviewsLog(value) {
    this.service.get('qa/controlsample.php?type=getReviewsLog&material_type='+value).subscribe(response => {
      this.results = response;
    });
  }

  close() {
    this.router.navigate(['/controlsample']);
  }

  getprint(){
    this.service.open('pdf1/controlsample.php?type=controlsamplelog&fromdate='+this.fromdate+'&todate='+this.todate);
  }

}
