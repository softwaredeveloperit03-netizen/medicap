import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OstatementComponent } from './ostatement.component';

describe('OstatementComponent', () => {
  let component: OstatementComponent;
  let fixture: ComponentFixture<OstatementComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ OstatementComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(OstatementComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
