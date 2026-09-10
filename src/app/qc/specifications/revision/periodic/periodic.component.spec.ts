import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PeriodicComponent } from './periodic.component';

describe('PeriodicComponent', () => {
  let component: PeriodicComponent;
  let fixture: ComponentFixture<PeriodicComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PeriodicComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(PeriodicComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
