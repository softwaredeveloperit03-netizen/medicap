import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DailyWorkComponent } from './daily-work.component';

describe('DailyWorkComponent', () => {
  let component: DailyWorkComponent;
  let fixture: ComponentFixture<DailyWorkComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DailyWorkComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DailyWorkComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
