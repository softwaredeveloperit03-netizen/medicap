import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TechreqComponent } from './techreq.component';

describe('TechreqComponent', () => {
  let component: TechreqComponent;
  let fixture: ComponentFixture<TechreqComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TechreqComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TechreqComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
