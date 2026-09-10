import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-ewastelogbook',
  templateUrl: './ewastelogbook.component.html',
  styleUrls: ['./ewastelogbook.component.css']
})
export class EwastelogbookComponent implements OnInit {


   isView = false;
   isNew = false;
   results;

    constructor(private service:DataAccessService) { }

    ngOnInit(): void {
      this.getDepartments();
      this.getEwasteLogBook();
      this.get_rights();
    }



  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  rights;

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id='
    +localStorage.getItem('emp_id') +'&dep_name=' +localStorage.getItem('department')
       )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
      });
  }






    departments;
    getDepartments() {
        this.service.get('it/it.php?type=getDepartments').subscribe((response: any) => {
        this.departments = response;
      });
    }

    getEwasteLogBook() {
        this.service.get('it/itTwo.php?type=getEwasteLogBook').subscribe((response: any) => {
        this.results = response;
      });
    }



  remark = '';


    saveEwsteEntry(data){

      if(!data.valid){
        alertify.error("All Field Required !!!!!!!!");
        return;
      }

      let temp = data.value;

      this.service.post('it/itTwo.php?type=saveEwsteEntry', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
          alert('Saved Successfully');
          this.getEwasteLogBook();
          this.isNew = false;
          data.reset();

          } else {
          alert('Failed: An error occured, please try again!');
        }
      });
    }



    destroy(prasad){
      const ans = confirm('Do You Want The Destroy The Item.....');
      if (ans) {

        let temp =  {};
        temp['id'] = prasad;
          this.service.post('it/itTwo.php?type=destroy', JSON.stringify(temp)).subscribe(response => {
            if (response['status'] == 'success') {
              alert('Destroyed Successfully');
              this.getEwasteLogBook();
              } else {
              alert('Failed: An error occured, please try again!');
            }
          });

      }
    }

    checking(prasad){
      const ans = confirm('Do You Want The Checked The Item.....');
      if (ans) {

        let temp =  {};
        temp['id'] = prasad;
          this.service.post('it/itTwo.php?type=checking', JSON.stringify(temp)).subscribe(response => {
            if (response['status'] == 'success') {
              alert('Checked Successfully');
              this.getEwasteLogBook();
              } else {
              alert('Failed: An error occured, please try again!');
            }
          });

      }
    }

    verify(prasad){

      const remark = prompt('Enter Remark.....');
      if (remark !== null) {

        let temp =  {};
        temp['id'] = prasad;
        temp['remark'] = remark;
          this.service.post('it/itTwo.php?type=verify', JSON.stringify(temp)).subscribe(response => {
            if (response['status'] == 'success') {
              alert('Verified Successfully');
              this.getEwasteLogBook();
              } else {
              alert('Failed: An error occured, please try again!');
            }
          });

      }
    }


  }
