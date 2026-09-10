  import { DatePipe } from '@angular/common';
  import { Component, OnInit } from '@angular/core';
  import { DataAccessService } from 'src/app/data-access.service';
  declare let alertify;  
 
@Component({
  selector: 'app-passbox',
  templateUrl: './passbox.component.html',
  styleUrls: ['./passbox.component.css'],
  providers: [DatePipe],
})
export class PassboxComponent implements OnInit {
  from_date = '';
  to_date = '';
  today = '';
  results;
  isNew = false;
  isView = false;
  selectedResult = [];
  ids;
  equipment_code = '';
  results1;
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
     this.loggedInDept = localStorage.getItem('department');
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getPassbox();
    this.getPassboxes();
    this.get_rights();
  }
  // -----------------------------------------12th july------------------------------------------//

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
      )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }
  //---------------------------------------------------------------------------------//
  getPassboxes() {
    this.service
      .get('microbiology/passbox.php?type=getPassboxes')
      .subscribe((response) => {
        this.ids = response;
      });
  }
  getPassbox() {
    this.service
      .get(
        'microbiology/passbox.php?type=getRecords&from_date=' +
          this.from_date +
          '&to_date=' +
          this.to_date
      )
      .subscribe((response) => {
        this.results1 = response;
        this.filterEquipment();
      });
  }

  filterEquipment() {
    this.results = [];
    for (let i = 0; i < this.results1.length; i++) {
      let material = this.results1[i];
      if (
        material['equipment_code']
          .toUpperCase()
          .includes(this.equipment_code.toUpperCase())
      ) {
        this.results[this.results.length] = material;
      }
    }
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  download() {
    this.service.open(
      'microbiology/passbox.php?type=downloadRecords&from_date=' +
        this.from_date +
        '&to_date=' +
        this.to_date
    );
  }

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.service
      .post('microbiology/passbox.php?type=saveRecord', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] === 'success') {
          this.getPassbox();
          alertify.success('Record Inserted successfully');
          this.isNew = false;
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
  }
}
  