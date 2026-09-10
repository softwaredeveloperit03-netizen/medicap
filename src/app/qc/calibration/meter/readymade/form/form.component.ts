import { Component, OnInit } from '@angular/core';
declare let alertify;

@Component({
  selector: 'app-form',
  templateUrl: './form.component.html',
  styleUrls: ['./form.component.css']
})
export class FormComponent implements OnInit {

  
  constructor() { }

  ngOnInit(): void {
  }
  saveform(data) {
    if(!data.valid){
      alertify.error('all feilds are required');
      return;
    }
  }
}
