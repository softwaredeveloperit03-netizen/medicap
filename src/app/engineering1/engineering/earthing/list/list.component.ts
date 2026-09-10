import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.css']
})
export class ListComponent implements OnInit {

 
  results;
  lists;

  selectedResult = [];
  constructor(private service:DataAccessService) { 
  }

  ngOnInit() {
    this.getList();
  }
  getList(){
    this.service.get('engineering/earthing.php?type=getEarthingPoints').subscribe(response=>{
      this.lists=response;
    });
  }
  download(){
    this.service.open('engineering/earthing.php?type=downloadEarthingPoints')
  }
}
