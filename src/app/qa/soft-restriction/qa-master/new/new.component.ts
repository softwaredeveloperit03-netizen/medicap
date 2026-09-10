import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  field: any;
  pro=false;
  raw=false;
  packing=false;
  testC=false;
  constructor() { }

  ngOnInit(): void {
  }

  save(val1:any){
    console.log(val1.value);
  }
}
