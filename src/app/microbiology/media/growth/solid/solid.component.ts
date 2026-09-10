  import { DatePipe } from '@angular/common';
  import { Component, OnInit } from '@angular/core';
  import { DataAccessService } from 'src/app/data-access.service';
  declare let alertify;  

@Component({
  selector: 'app-solid',
  templateUrl: './solid.component.html',
  styleUrls: ['./solid.component.css'],
  providers: [DatePipe],
})
export class SolidComponent implements OnInit {
  from_date = '';
  to_date = '';
  today = '';
  results;
  isNew = false;
  medias;
  batches;
  observationList = [];
  isView = false;
  satisfactory = '';
  selectedResult = [];
  flag1 = false;
  flag2 = false;
  flag3 = false;
  flag4 = false;
  flag5 = false;
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
     this.loggedInDept = localStorage.getItem('department');
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getMedia();
    this.getMediaMaster();
    this.getbatches();
    this.get_rights();
  }

  getMediaMaster() {
    this.service.get('master/media.php?type=getMedia').subscribe((response) => {
      this.medias = response;
    });
  }
  getbatches() {
    this.service
      .get('master/media.php?type=getbatches')
      .subscribe((response) => {
        this.batches = response;
      });
  }

  getMedia() {
    this.service
      .get(
        'microbiology/media.php?type=getGrowthSolidMediaLog&from_date=' +
          this.from_date +
          '&to_date=' +
          this.to_date
      )
      .subscribe((response) => {
        this.results = response;
      });
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.observationList = JSON.parse(this.selectedResult['observations']);
    this.isView = true;
  }
  getBatch(index) {
    index = index - 1;
    if (index !== -1) {
      let BATCHES = this.medias[index];
      this.batches = BATCHES['batches'];
    }
  }

  add(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.observationList[this.observationList.length] = temp;
    data.resetForm();
  }

  del(index) {
    this.observationList.splice(index, 1);
  }

  download() {
    this.service.open(
      'microbiology/media.php?type=downloadGrowthSolidMediaLog&from_date=' +
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
    temp['observations'] = this.observationList;
    temp['satisfactory'] = this.satisfactory;
    this.service
      .post(
        'microbiology/media.php?type=saveGrowthSolidMedia',
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        if (response['status'] === 'success') {
          this.getMedia();
          alertify.success('Record Inserted successfully');
          this.isNew = false;
          this.observationList.length = 0;
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
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

  onValueChange1(newValue: string) {
    const batchNo = /^\d+$/;
    const isValidBatchNo = batchNo.test(newValue);

    if (isValidBatchNo) {
      this.flag1 = false;
    } else {
      this.flag1 = true;
    }
  }

  onValueChange2(newValue: string) {
    const batchNo = /^\d+$/;
    const isValidBatchNo = batchNo.test(newValue);

    if (isValidBatchNo) {
      this.flag2 = false;
    } else {
      this.flag2 = true;
    }
  }

  onValueChange3(newValue: string) {
    const batchNo = /^\d+$/;
    const isValidBatchNo = batchNo.test(newValue);

    if (isValidBatchNo) {
      this.flag3 = false;
    } else {
      this.flag3 = true;
    }
  }
  onValueChange4(newValue: string) {
    const batchNo = /^\d+$/;
    const isValidBatchNo = batchNo.test(newValue);

    if (isValidBatchNo) {
      this.flag4 = false;
    } else {
      this.flag4 = true;
    }
  }
}
  