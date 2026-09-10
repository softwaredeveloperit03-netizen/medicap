import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InvestigationReportComponent } from './investigation-report.component';

describe('InvestigationReportComponent', () => {
  let component: InvestigationReportComponent;
  let fixture: ComponentFixture<InvestigationReportComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InvestigationReportComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(InvestigationReportComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
