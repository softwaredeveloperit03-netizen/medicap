import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RivisopComponent } from './rivisop.component';

describe('RivisopComponent', () => {
  let component: RivisopComponent;
  let fixture: ComponentFixture<RivisopComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RivisopComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RivisopComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
