import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PhmeterComponent } from './phmeter.component';

describe('PhmeterComponent', () => {
  let component: PhmeterComponent;
  let fixture: ComponentFixture<PhmeterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PhmeterComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PhmeterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
