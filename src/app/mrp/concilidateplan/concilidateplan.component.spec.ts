import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ConcilidateplanComponent } from './concilidateplan.component';

describe('ConcilidateplanComponent', () => {
  let component: ConcilidateplanComponent;
  let fixture: ComponentFixture<ConcilidateplanComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ConcilidateplanComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ConcilidateplanComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
