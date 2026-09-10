import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-receive',
  templateUrl: './receive.component.html',
  styleUrls: ['./receive.component.css']
})
export class ReceiveComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingReceiving();
  }

  getPendingReceiving(){
    this.service.get('production/technical.php?type=getPendingReceiving').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }


}
