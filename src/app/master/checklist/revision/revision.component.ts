import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-revision',
  templateUrl: './revision.component.html',
  styleUrls: ['./revision.component.css']
})
export class RevisionComponent implements OnInit {
  isView: false;
  results;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getArea();

  }
  getArea(){
    this.service.get('sales/headquarter.php?type=getPendingHeadquarters').subscribe(response=>{
      this.results=response;
    })
  }
}
