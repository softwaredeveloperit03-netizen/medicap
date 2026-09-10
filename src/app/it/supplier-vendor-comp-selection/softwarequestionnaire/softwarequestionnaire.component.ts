import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-softwarequestionnaire',
  templateUrl: './softwarequestionnaire.component.html',
  styleUrls: ['./softwarequestionnaire.component.css']
})
export class SoftwarequestionnaireComponent implements OnInit {

  isNew=false;

  constructor() { }

  ngOnInit(): void {
  }

  softwareQues(){
    this.isNew=true;
  }

}
