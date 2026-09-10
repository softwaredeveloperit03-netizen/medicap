import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DaikinComponent } from './daikin.component';

describe('DaikinComponent', () => {
  let component: DaikinComponent;
  let fixture: ComponentFixture<DaikinComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DaikinComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DaikinComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
