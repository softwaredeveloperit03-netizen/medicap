import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ScrapReportComponent } from './scrap-report.component';

describe('ScrapReportComponent', () => {
  let component: ScrapReportComponent;
  let fixture: ComponentFixture<ScrapReportComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ScrapReportComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ScrapReportComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
