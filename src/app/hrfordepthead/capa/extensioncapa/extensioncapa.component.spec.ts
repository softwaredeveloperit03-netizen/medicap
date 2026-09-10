import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EXTENSIONCAPAComponent } from './extensioncapa.component';

describe('EXTENSIONCAPAComponent', () => {
  let component: EXTENSIONCAPAComponent;
  let fixture: ComponentFixture<EXTENSIONCAPAComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EXTENSIONCAPAComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EXTENSIONCAPAComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
