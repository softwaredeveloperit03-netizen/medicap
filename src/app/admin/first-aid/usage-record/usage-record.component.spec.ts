import { ComponentFixture, TestBed } from '@angular/core/testing';

import { UsageRecordComponent } from './usage-record.component';

describe('UsageRecordComponent', () => {
  let component: UsageRecordComponent;
  let fixture: ComponentFixture<UsageRecordComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ UsageRecordComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(UsageRecordComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
