import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PolarimeterComponent } from './polarimeter.component';

describe('PolarimeterComponent', () => {
  let component: PolarimeterComponent;
  let fixture: ComponentFixture<PolarimeterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PolarimeterComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(PolarimeterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
