import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {
  results;
  isView = false;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getReceiving()
  }
  getReceiving(){
    this.service.get('packing/semifinish.php?type=getAwaitingPacking').subscribe(response=>{
      this.results=response;
    })
  }
  receive(id){
    this.service.get('packing/semifinish.php?type=receiveProduct&id='+id).subscribe(response=>{
      if(response['status'] =='success'){
        alert("received successfully");
        this.getReceiving()
      }
      else{
        alert("error occured!")
      }
    });
  }

}
