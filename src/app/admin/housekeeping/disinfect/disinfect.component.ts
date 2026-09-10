import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-disinfect',
  templateUrl: './disinfect.component.html',
  styleUrls: ['./disinfect.component.css']
})
export class DisinfectComponent implements OnInit {
   
  departments;
  ingredientsform= []; 
  formequipment= []; 
  
  constructor(private service: DataAccessService,private router: Router ) {}

  ngOnInit(): void {
    this.getDepartments();
  }

getDepartments(){
  this.service.get('qa/all2.php?type=getDepartments').subscribe((response:any) => {
    this.departments = response;
  });
}

addlabel(data) {
  if (!data.valid) {
    alert('All fields are required');
    return;
  }
 
  let temp = data.value;
  let tempData = [];
  this.ingredientsform[this.ingredientsform.length] = temp;
  console.log(this.ingredientsform);
  data.resetForm();
}

addlabel2(data) {
  if (!data.valid) {
    alert('All fields are required');
    return;
  }
  let temp = data.value;
  let tempData = [];
  this.formequipment.push(temp);
  // this.formequipment[this.formequipment.length] = temp;
  console.log(this.formequipment);
  data.resetForm();
}

save(data) {
 this.service.post('qa/all.php?type=savedisinfectant_pre_recordform',JSON.stringify(data.value)).subscribe(response => {
    if (response['status'] == 'success') {
      alert('Saved Successfully');
    // this.router.navigate(['/checklist']);
  } else {
    console.log(response);
    alert('Failed: An error occured, please try again!');
  }
});
}

  delData(index) {
    this.ingredientsform.splice(index, 1);
  }

  delData2(index) {
    this.formequipment.splice(index, 1);
  }

}
