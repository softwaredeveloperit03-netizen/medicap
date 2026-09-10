import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';
@Component({
  selector: 'app-attendance',
  templateUrl: './attendance.component.html',
  styleUrls: ['./attendance.component.css']
})
export class AttendanceComponent implements OnInit {

  attendances;
  from_date;
  to_date;
  labour_id = '';
  contractor = '';
  total_days = 0;
  total_ot_hours = 0;
  total_ot_salary = 0;
  isCorrection = false;

  entry_time;
  out_time;
  labour_no;
  id;
  entry_date;
  out_date;
  constructor(private service: DataAccessService) {
    this.from_date = this.formatDate(new Date());
    this.to_date = this.from_date;
   }

  ngOnInit() {
    this.getLabourAttendance();
  }

  getLabourAttendance() {
    this.service.get('hrDepartment.php?type=getLabourAttendence&from_date=' + this.from_date + '&to_date='+ this.to_date +'&labour_id=' + this.labour_id + '&contractor=' + this.contractor).subscribe(response => {
      this.attendances = response;
      let len = Object.keys(this.attendances).length;
      for (let i = 0; i < len; i++) {
        let data = this.attendances[i];
        this.total_days += 1;
        this.total_ot_hours += +data["ot_hours"];
        this.total_ot_salary += +data["ot_total"];
      }
    });
  }
  getLabourAttendance1() {
    this.service.get('hrDepartment.php?type=getLabourAttendence1&from_date=' + this.from_date + '&to_date='+ this.to_date +'&labour_id=' + this.labour_id + '&contractor=' + this.contractor).subscribe(response => {
      this.attendances = response;
      let len = Object.keys(this.attendances).length;
      for (let i = 0; i < len; i++) {
        let data = this.attendances[i];
        this.total_days += 1;
        this.total_ot_hours += +data["ot_hours"];
        this.total_ot_salary += +data["ot_total"];
      }
    });
  }

  formatDate(date) {
    var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;

    return [year, month, day].join('-');
  }

  formatDate1(date) {
    var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;

    return [year, month, day].join('/');
  }

  downloadExcel() {
    let url= this.service.url + 'reports/labour_attendance.php?from_date=' + this.from_date + '&to_date='+ this.to_date +'&labour_id=' + this.labour_id + '&contractor=' + this.contractor;
    window.open(url, '_blank');
  }

  errorCorrection(index) {
    this.labour_no = this.attendances[index].labour_no;
    this.id = this.attendances[index].id;
    let e1 = new Date(this.attendances[index].in_time);
    let temp = this.attendances[index].out_time;
    let e2;
    if (temp.length > 3) {
      e2 = new Date(this.attendances[index].out_time);
      this.out_date = e2.toLocaleDateString();
      this.out_time = e2.toLocaleTimeString();
    } else {
      e2 = this.formatDate1(new Date());
      this.out_date = e2;
    }
    this.entry_time = e1.toLocaleTimeString();
    this.entry_date = e1.toLocaleDateString();
    this.isCorrection = true;
  }

  updateAttendance(data) {
    let temp = data.value;
    temp['id'] = this.id;
    temp['entry_date'] = this.entry_date;
    temp['out_date'] = this.out_date;
    this.service.post('hrDepartment.php?type=updateLabourAttendence', JSON.stringify(temp)).subscribe(response => {
      this.isCorrection = false;
      this.getLabourAttendance();
    });
  }

  exportToExcel(): void {
    const formattedData = this.attendances.map((attendance, index) => ({
      'Labour ID': attendance.labour_no,
      'Labour Name': attendance.labour_name,
      'In Time': this.formatDate2(attendance.in_time),
      'Out Time': this.formatDate2(attendance.out_time),
      'Entry By': attendance.entry_by,
      'Out By': attendance.out_by,
      'Total Hours': attendance.total_hours,
      'OT Hours': attendance.ot_hours,
      'OT Pay': attendance.ot_total
    }));

    const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(formattedData);
    const workbook: XLSX.WorkBook = {
      Sheets: { 'data': worksheet },
      SheetNames: ['data']
    };

    XLSX.writeFile(workbook, 'AttendanceData.xlsx');
  }

  formatDate2(dateString: string): string {
    const date = new Date(dateString);
    const day = ('0' + date.getDate()).slice(-2);
    const month = ('0' + (date.getMonth() + 1)).slice(-2);
    const year = date.getFullYear();
    return `${day}-${month}-${year}`;
  }

}
