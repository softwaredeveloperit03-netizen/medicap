import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AuditLogReportComponent } from './audit-log-report.component';

describe('AuditLogReportComponent', () => {
  let component: AuditLogReportComponent;
  let fixture: ComponentFixture<AuditLogReportComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AuditLogReportComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AuditLogReportComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
