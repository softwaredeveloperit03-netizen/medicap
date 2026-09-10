import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-master',
  templateUrl: './master.component.html',
  styleUrls: ['./master.component.css']
})
export class MasterComponent implements OnInit {

  constructor() { }
module
  ngOnInit(): void {
  }

    modules=[]
    addModule(){
    let temp ={};
    temp['module']=this.module;
    this.modules[this.modules.length]=temp
  }

}
