import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OocComponent } from './ooc.component';

describe('OocComponent', () => {
  let component: OocComponent;
  let fixture: ComponentFixture<OocComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ OocComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(OocComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
