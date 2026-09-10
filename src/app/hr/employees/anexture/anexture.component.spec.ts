import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AnextureComponent } from './anexture.component';

describe('AnextureComponent', () => {
  let component: AnextureComponent;
  let fixture: ComponentFixture<AnextureComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AnextureComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AnextureComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
