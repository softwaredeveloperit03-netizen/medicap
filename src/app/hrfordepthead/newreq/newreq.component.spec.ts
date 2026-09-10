import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NewreqComponent } from './newreq.component';

describe('NewreqComponent', () => {
  let component: NewreqComponent;
  let fixture: ComponentFixture<NewreqComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ NewreqComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(NewreqComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
