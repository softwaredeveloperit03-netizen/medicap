import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CaplistComponent } from './caplist.component';

describe('CaplistComponent', () => {
  let component: CaplistComponent;
  let fixture: ComponentFixture<CaplistComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CaplistComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CaplistComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
