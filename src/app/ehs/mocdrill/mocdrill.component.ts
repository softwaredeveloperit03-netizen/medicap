import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-mocdrill',
  templateUrl: './mocdrill.component.html',
  styleUrls: ['./mocdrill.component.css']
})
export class MocdrillComponent implements OnInit {

  //stpmockdrill;

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getstpmockdrill();
    this.getSection();
    this.GET_InvolvedPersons();
  }

  stpmockdrill;
  getstpmockdrill(){
    this.service.get('common.php?type=getstpmockdrill').subscribe(response =>{
        this.stpmockdrill =response
      });
  }

  Sections;

  getSection() {
    this.service
      .get('master/section.php?type=getSection')
      .subscribe((response) => {
        this.Sections = response;
      });
  }
   
  selectedEmp=[];
    getEmpdata(i){

      this.selectedEmp=this.InvolvedPersons[i-1];
    }

   InvolvedPersons: any = [];
  GET_InvolvedPersons() {
    this.service
      .get('common.php?type=AllEmployeeList')
      .subscribe((response) => {
        this.InvolvedPersons = response;
      });
  }
  // addcleaning(data){
  //   let temp = data.value;
  //   console.log(temp);
  //   this.service.post('',JSON.stringify(temp)).subscribe(response => {
  //    console.log('Form saved Successfully);
  // });
  // }

  submitMocDrill(data){
    let temp = data.value;
    console.log(temp);
    this.service.post('ehs/mockdrill.php?type=saveMockDrill',JSON.stringify(temp)).subscribe(response => {
        if(response['status'] == 'success'){
          alertify.success('Mock Drill Saved Successfully');
          this.router.navigateByUrl('/ehs/mockdrill');
        }
        else{
          alertify.error(response['status']);
        }
    });
  }
}
